<?php
/**
 * This class defines some helper functions to automatically flush cache
 * plugins when experiment statuses change.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/utils
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      5.0.0
 */
defined( 'ABSPATH' ) || exit;

/**
 * This class defines some helper functions to automatically flush cache
 * plugins when experiment statuses change.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/utils
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      5.0.0
 */
class Nelio_AB_Testing_Cache_Manager {

	/**
	 * The single instance of this class.
	 *
	 * @since  5.0.0
	 * @access protected
	 * @var    Nelio_AB_Testing_Cache_Manager
	 */
	protected static $instance;

	/**
	 * Returns the single instance of this class.
	 *
	 * @return Nelio_AB_Testing_Cache_Manager the single instance of this class.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public static function instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}//end if

		return self::$instance;

	}//end instance()

	/**
	 * Hooks into WordPress.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function init() {

		add_action( 'nab_start_experiment', array( $this, 'trigger_flush_all_caches' ) );
		add_action( 'nab_pause_experiment', array( $this, 'trigger_flush_all_caches' ) );
		add_action( 'nab_resume_experiment', array( $this, 'trigger_flush_all_caches' ) );
		add_action( 'nab_stop_experiment', array( $this, 'trigger_flush_all_caches' ) );

		add_action( 'nab_flush_all_caches', array( $this, 'flush_all_compatible_caches' ), 9 );

	}//end init()

	/**
	 * Triggers an event to flush all caches.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function trigger_flush_all_caches() {

		/**
		 * Triggers a request to flush all compatible caches.
		 *
		 * By default, this action fires when an experiment is started, stopped,
		 * paused, or resumed. Hook into this action to add compatibility with
		 * your own cache plugin.
		 *
		 * @since 5.0.0
		 */
		do_action( 'nab_flush_all_caches' );

	}//end trigger_flush_all_caches()

	/**
	 * Flushes all caches from compatible chache plugins.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function flush_all_compatible_caches() {

		$this->flush_wordpress_cache();
		$this->flush_w3_total_cache();
		$this->flush_wp_super_cache();
		$this->flush_wpengine_cache();
		$this->flush_wp_fastest_cache();
		$this->flush_kinsta_cache();
		$this->flush_godaddy_cache();
		$this->flush_wp_optimize_cache();
		$this->flush_breeze_cache();
		$this->flush_litespeed_cache();
		$this->flush_siteground_cache();
		$this->flush_autoptimize_cache();
		$this->flush_wp_rocket_cache();
		$this->flush_nitropack_cache();

	}//end flush_all_compatible_caches()

	private function flush_wordpress_cache() {

		if ( ! function_exists( 'wp_cache_flush' ) ) {
			return;
		}//end if

		wp_cache_flush();

	}//end flush_wordpress_cache()

	private function flush_w3_total_cache() {

		if ( ! class_exists( 'W3_Plugin_TotalCacheAdmin' ) ) {
			return;
		}//end if

		$plugin = & w3_instance( 'W3_Plugin_TotalCacheAdmin' );
		$plugin->flush_all();

	}//end flush_w3_total_cache()

	private function flush_wp_super_cache() {

		if ( ! function_exists( 'wp_cache_clean_cache' ) ) {
			return;
		}//end if

		global $file_prefix, $supercachedir;
		if ( empty( $supercachedir ) && function_exists( 'get_supercache_dir' ) ) {
			$supercachedir = get_supercache_dir();
		}//end if

		wp_cache_clean_cache( $file_prefix );

	}//end flush_wp_super_cache()

	private function flush_wpengine_cache() {

		if ( ! class_exists( 'WpeCommon' ) ) {
			return;
		}//end if

		if ( method_exists( 'WpeCommon', 'purge_memcached' ) ) {
			WpeCommon::purge_memcached();
		}//end if

		if ( method_exists( 'WpeCommon', 'clear_maxcdn_cache' ) ) {
			WpeCommon::clear_maxcdn_cache();
		}//end if

		if ( method_exists( 'WpeCommon', 'purge_varnish_cache' ) ) {
			WpeCommon::purge_varnish_cache();
		}//end if

	}//end flush_wpengine_cache()

	private function flush_wp_fastest_cache() {

		global $wp_fastest_cache;
		if ( method_exists( 'WpFastestCache', 'deleteCache' ) && ! empty( $wp_fastest_cache ) ) {
			$wp_fastest_cache->deleteCache( true );
		}//end if

	}//end flush_wp_fastest_cache()

	private function flush_kinsta_cache() {

		global $kinsta_cache;
		if ( class_exists( '\Kinsta\Cache' ) && ! empty( $kinsta_cache ) ) {
			$kinsta_cache->kinsta_cache_purge->purge_complete_caches();
		}//end if

	}//end flush_kinsta_cache()

	private function flush_godaddy_cache() {

		if ( class_exists( '\WPaaS\Cache' ) && function_exists( 'ccfm_godaddy_purge' ) ) {
			ccfm_godaddy_purge();
		}//end if

	}//end flush_godaddy_cache()

	private function flush_wp_optimize_cache() {

		if ( ! class_exists( 'WP_Optimize' ) || ! defined( 'WPO_PLUGIN_MAIN_PATH' ) ) {
			return;
		}//end if

		if ( ! class_exists( 'WP_Optimize_Cache_Commands' ) ) {
			include_once WPO_PLUGIN_MAIN_PATH . 'cache/class-cache-commands.php';
		}//end if

		if ( class_exists( 'WP_Optimize_Cache_Commands' ) ) {
			$wpoptimize_cache_commands = new WP_Optimize_Cache_Commands();
			$wpoptimize_cache_commands->purge_page_cache();
		}//end if

	}//end flush_wp_optimize_cache()

	private function flush_breeze_cache() {

		global $admin;
		if ( class_exists( 'Breeze_Admin' ) && ! empty( $admin ) && ( $admin instanceof Breeze_Admin ) ) {
			$admin->breeze_clear_all_cache();
		}//end if

	}//end flush_breeze_cache()

	private function flush_litespeed_cache() {

		if ( class_exists( 'LiteSpeed_Cache_Purge' ) ) {
			LiteSpeed_Cache_Purge::purge_all();
		}//end if

		if ( class_exists( 'Purge' ) ) {
			Purge::purge_all();
		}//end if

	}//end flush_litespeed_cache()

	private function flush_siteground_cache() {

		if ( function_exists( 'sg_cachepress_purge_cache' ) ) {
			sg_cachepress_purge_cache();
		}//end if

	}//end flush_siteground_cache()

	private function flush_autoptimize_cache() {

		if ( class_exists( 'autoptimizeCache' ) ) {
			autoptimizeCache::clearall();
		}//end if

	}//end flush_autoptimize_cache()

	private function flush_wp_rocket_cache() {

		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		}//end if

	}//end flush_wp_rocket_cache()

	private function flush_nitropack_cache() {

		if ( function_exists( 'nitropack_sdk_purge_local' ) ) {
			nitropack_sdk_purge_local();
		}//end if

	}//end flush_nitropack_cache()

}//end class
