<?php
/**
 * This file contains the class that defines REST API endpoints for templates.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/rest
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

class Nelio_AB_Testing_Template_REST_Controller extends WP_REST_Controller {

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
			'/templates/',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_templates' ),
					'permission_callback' => array( $this, 'check_if_user_can_use_plugin' ),
					'args'                => $this->get_collection_params(),
				),
			)
		);

	}//end register_routes()

	/**
	 * Returns all templates.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response The response
	 */
	public function get_templates( $request ) {

		$args = array(
			'public'   => true,
			'_builtin' => false,
		);

		$post_types = get_post_types( $args, 'names', 'and' );
		$post_types = array_unique( array_merge( $post_types, array( 'page', 'post' ) ) );

		$result = array();
		foreach ( $post_types as $post_type ) {
			$result[ $post_type ] = $this->get_templates_in_post_type( $post_type );
		}//end foreach

		$data = array(
			'results'    => $result,
			'pagination' => array(
				'more'  => false,
				'pages' => 1,
			),
		);
		return new WP_REST_Response( $data, 200 );

	}//end get_templates()

	public function check_if_user_can_use_plugin() {
		return current_user_can( 'edit_others_posts' );
	}//end check_if_user_can_use_plugin()

	/**
	 * Get the query params for collections
	 *
	 * @return array
	 */
	public function get_collection_params() {
		return array(
			'type' => array(
				'description'       => 'Limit results to those matching a post type.',
				'type'              => 'string',
				'default'           => 'post',
				'sanitize_callback' => 'sanitize_text_field',
			),
		);
	}//end get_collection_params()

	private function get_templates_in_post_type( $post_type ) {

		$templates = wp_get_theme()->get_page_templates( null, $post_type );
		$templates = array_map(
			function ( $id, $name ) {
				return array(
					'id'   => $id,
					'name' => $name,
				);
			},
			array_keys( $templates ),
			array_values( $templates )
		);

		usort(
			$templates,
			function( $a, $b ) {
				return strcasecmp( $a['name'], $b['name'] );
			}
		);

		if ( count( $templates ) ) {
			$templates = array_merge(
				array(
					array(
						'id'   => '_nab_default_template',
						'name' => _x( 'Default template', 'text', 'nelio-ab-testing' ),
					),
				),
				$templates
			);
		}//end if

		return $templates;

	}//end get_templates_in_post_type()

}//end class
