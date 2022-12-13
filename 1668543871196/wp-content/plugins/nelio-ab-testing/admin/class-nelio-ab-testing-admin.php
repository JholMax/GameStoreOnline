<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * The admin-specific functionality of the plugin.
 */
class Nelio_AB_Testing_Admin {

	protected static $instance;

	public static function instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}//end if

		return self::$instance;

	}//end instance()

	public function init() {

		add_action( 'admin_menu', array( $this, 'create_menu_pages' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_assets' ), 5 );
		add_filter( 'option_page_capability_nelio-ab-testing_group', array( $this, 'get_settings_capability' ) );

	}//end init()

	public function create_menu_pages() {

		add_menu_page(
			'Nelio A/B Testing',
			'Nelio A/B Testing',
			nelioab()->is_ready() ? 'edit_others_posts' : 'manage_options',
			'nelio-ab-testing',
			null,
			$this->get_plugin_icon(),
			25
		);

		if ( ! nelioab()->is_ready() ) {
			$page = new Nelio_AB_Testing_Welcome_Page();
			$page->init();
			return;
		}//end if

		$page = new Nelio_AB_Testing_Overview_Page();
		$page->init();

		$page = new Nelio_AB_Testing_Experiment_List_Page();
		$page->init();

		$page = new Nelio_AB_Testing_Experiment_Page();
		$page->init();

		$page = new Nelio_AB_Testing_Results_Page();
		$page->init();

		$page = new Nelio_AB_Testing_Account_Page();
		$page->init();

		$page = new Nelio_AB_Testing_Settings_Page();
		$page->init();

		$page = new Nelio_AB_Testing_Help_Page();
		$page->init();

		$page = new Nelio_AB_Testing_Plugin_List_Page();
		$page->init();

	}//end create_menu_pages()

	public function register_assets() {

		$url     = nelioab()->plugin_url;
		$path    = nelioab()->plugin_path;
		$version = nelioab()->plugin_version;

		$scripts = array(
			'nab-components',
			'nab-conversion-action-library',
			'nab-conversion-actions',
			'nab-data',
			'nab-date',
			'nab-editor',
			'nab-experiment-library',
			'nab-experiments',
			'nab-heatmap-editor',
			'nab-i18n',
			'nab-segmentation-rule-library',
			'nab-segmentation-rules',
			'nab-utils',
		);

		foreach ( $scripts as $script ) {
			$file_without_ext = preg_replace( '/^nab-/', '', $script );
			nab_register_script_with_auto_deps( $script, $file_without_ext, true );
		}//end foreach

		wp_add_inline_script(
			'nab-data',
			sprintf(
				'wp.data.dispatch( %s ).receivePluginSettings( %s );' .
				'wp.data.dispatch( %s ).receiveWooCommerceSettings( %s );',
				wp_json_encode( 'nab/data' ),
				wp_json_encode( $this->get_plugin_settings() ),
				wp_json_encode( 'nab/data' ),
				wp_json_encode( $this->get_woocommerce_settings() )
			)
		);

		wp_localize_script(
			'nab-i18n',
			'nabI18n',
			array(
				'locale' => str_replace( '_', '-', get_locale() ),
			)
		);

		wp_register_style(
			'nab-components',
			$url . '/assets/dist/css/components.css',
			array( 'wp-admin', 'wp-components' ),
			$version
		);

		wp_register_style(
			'nab-conversion-action-library',
			$url . '/assets/dist/css/conversion-action-library.css',
			array(),
			$version
		);

		wp_register_style(
			'nab-segmentation-rule-library',
			$url . '/assets/dist/css/segmentation-rule-library.css',
			array(),
			$version
		);

		wp_register_style(
			'nab-editor',
			$url . '/assets/dist/css/editor.css',
			array( 'nab-components', 'nab-experiment-library', 'nab-conversion-action-library', 'nab-segmentation-rule-library', 'wp-edit-post' ),
			$version
		);

		wp_register_style(
			'nab-experiment-library',
			$url . '/assets/dist/css/experiment-library.css',
			array( 'nab-components' ),
			$version
		);

		wp_register_style(
			'nab-heatmap-editor',
			$url . '/assets/dist/css/heatmap-editor.css',
			array( 'nab-editor' ),
			$version
		);

	}//end register_assets()

	public function get_settings_capability() {
		return 'edit_others_posts';
	}//end get_settings_capability()

	private function get_plugin_icon() {

		$svg_icon_file = nelioab()->plugin_path . '/assets/dist/images/logo.svg';
		if ( ! file_exists( $svg_icon_file ) ) {
			return false;
		}//end if

		return 'data:image/svg+xml;base64,' . base64_encode( file_get_contents( $svg_icon_file ) ); // phpcs:ignore

	}//end get_plugin_icon()

	private function get_plugin_settings() {
		$settings = Nelio_AB_Testing_Settings::instance();
		return array(
			'areAutoTutorialsEnabled' => $settings->get( 'are_auto_tutorials_enabled' ),
			'externalTrackingScript'  => get_rest_url( null, nelioab()->rest_namespace . '/external.js' ),
			'homeUrl'                 => nab_home_url(),
			'minConfidence'           => $settings->get( 'min_confidence' ),
			'minSampleSize'           => $settings->get( 'min_sample_size' ),
			'siteId'                  => nab_get_site_id(),
			'subscription'            => nab_get_subscription(),
			'trackingUrl'             => nab_get_api_url( '/site/' . nab_get_site_id() . '/event', 'browser' ),
			'themeSupport'            => array(
				'menus'   => current_theme_supports( 'menus' ),
				'widgets' => current_theme_supports( 'widgets' ),
			),
		);
	}//end get_plugin_settings()

	private function get_woocommerce_settings() {
		$statuses = function_exists( 'wc_get_order_statuses' ) ? wc_get_order_statuses() : array();
		$statuses = array_map(
			function ( $key, $value ) {
				return array(
					'value' => $key,
					'label' => $value,
				);
			},
			array_keys( $statuses ),
			array_values( $statuses )
		);
		return array(
			'currencySymbol' => function_exists( 'get_woocommerce_currency_symbol' ) ? html_entity_decode( get_woocommerce_currency_symbol() ) : '$',
			'orderStatuses'  => $statuses,
		);
	}//end get_woocommerce_settings()

}//end class
