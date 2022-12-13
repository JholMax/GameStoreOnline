<?php
/**
 * This file contains the class that defines REST API endpoints for themes.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/rest
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

class Nelio_AB_Testing_Theme_REST_Controller extends WP_REST_Controller {

	/**
	 * The single instance of this class.
	 *
	 * @since  5.0.0
	 * @access protected
	 * @var    Nelio_AB_Testing_REST_API
	 */
	protected static $instance;

	/**
	 * Returns the single instance of this class.
	 *
	 * @return Nelio_AB_Testing_REST_API the single instance of this class.
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

		add_action( 'rest_api_init', array( $this, 'register_routes' ) );

	}//end init()

	/**
	 * Register the routes for the objects of the controller.
	 */
	public function register_routes() {

		register_rest_route(
			nelioab()->rest_namespace,
			'/themes/',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_themes' ),
					'permission_callback' => array( $this, 'check_if_user_can_use_plugin' ),
				),
			)
		);

	}//end register_routes()

	/**
	 * Returns all themes.
	 *
	 * @return WP_REST_Response The response
	 */
	public function get_themes() {

		$themes = wp_get_themes( array( 'allowed' => true ) );
		usort(
			$themes,
			function( $a, $b ) {
				return strcasecmp( $a['Name'], $b['Name'] );
			}
		);

		$data = array(
			'results'    => array_map( array( $this, 'build_theme_json' ), $themes ),
			'pagination' => array(
				'more'  => false,
				'pages' => 1,
			),
		);
		return new WP_REST_Response( $data, 200 );

	}//end get_themes()

	public function check_if_user_can_use_plugin() {
		return current_user_can( 'edit_others_posts' );
	}//end check_if_user_can_use_plugin()

	private function build_theme_json( $theme ) {

		return array(
			'id'    => $theme['Stylesheet'],
			'image' => $theme->get_screenshot(),
			'name'  => $theme['Name'],
		);
	}//end build_theme_json()

}//end class
