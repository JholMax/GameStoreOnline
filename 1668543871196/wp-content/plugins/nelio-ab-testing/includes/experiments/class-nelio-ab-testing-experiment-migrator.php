<?php
/**
 * This class migrates old experiments to Nelio A/B Testing 5.0 format.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/utils
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class migrates old experiments to Nelio A/B Testing 5.0 format.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/utils
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      5.0.0
 */
class Nelio_AB_Testing_Experiment_Migrator {

	/**
	 * The single instance of this class.
	 *
	 * @since  5.0.0
	 * @access protected
	 * @var    Nelio_AB_Testing_Experiment_Migrator
	 */
	protected static $instance;

	/**
	 * Returns the single instance of this class.
	 *
	 * @return Nelio_AB_Testing_Experiment_Migrator the single instance of this class.
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
	 * Registers the route for migrating a single experiment.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function register_routes() {

		register_rest_route(
			nelioab()->rest_namespace,
			'/migrate/experiment/(?P<id>[\d]+)',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'migrate_experiment_callback' ),
					'permission_callback' => array( $this, 'migration_permissions_check' ),
					'args'                => array(),
				),
			)
		);

		register_rest_route(
			nelioab()->rest_namespace,
			'/migrate/remove-old-account-data',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'remove_old_account_data_callback' ),
					'permission_callback' => array( $this, 'migration_permissions_check' ),
					'args'                => array(),
				),
			)
		);

	}//end register_routes()

	/**
	 * Migrate experiment callback.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response The response
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function migrate_experiment_callback( $request ) {

		try {
			$this->migrate_experiment( $request['id'] );
		} catch ( Exception $e ) { // phpcs:ignore
			return new WP_REST_Response( false, 500 );
		}//end try

		return new WP_REST_Response( true, 200 );

	}//end migrate_experiment_callback()

	/**
	 * Remove old account data.
	 *
	 * @return WP_REST_Response The response
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function remove_old_account_data_callback() {

		global $wpdb;
		$experiments = $wpdb->get_col( // phpcs:ignore
			$wpdb->prepare(
				"SELECT ID FROM $wpdb->posts WHERE post_type = %s",
				'nelioab_local_exp'
			)
		);

		foreach ( $experiments as $experiment_id ) {
			wp_delete_post( $experiment_id );
		}//end foreach

		delete_option( '__nelio_ab_used_free_trial' );
		$wpdb->query( // phpcs:ignore
			"DELETE FROM $wpdb->options
			WHERE option_name LIKE '%nelioab%'"
		);

		delete_option( 'nab_migrate_old_experiments' );

		return new WP_REST_Response( true, 200 );

	}//end remove_old_account_data_callback()

	/**
	 * Registers the route for migrating a single experiment.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response The response
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function migration_permissions_check( $request ) {
		return current_user_can( 'manage_options' );
	}//end migration_permissions_check()

	/**
	 * Migrates the given experiment to Nelio A/B Testing 5.0.
	 *
	 * @param int $old_experiment_id the experiment ID.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function migrate_experiment( $old_experiment_id ) {

		if ( ! $this->is_old_experiment( $old_experiment_id ) ) {
			return;
		}//end if

		update_option( 'nab_migrate_old_experiments', true );

		$old_experiment    = $this->get_old_experiment( $old_experiment_id );
		$new_experiment_id = $this->create_new_experiment( $old_experiment );

		switch ( $old_experiment['kind'] ) {

			case 'PageAlternativeExperiment':
			case 'PostAlternativeExperiment':
			case 'CptAlternativeExperiment':
				$this->migrate_post_experiment( $old_experiment, $new_experiment_id );
				break;

			case 'HeadlineAlternativeExperiment':
			case 'WC_ProductSummaryAlternativeExperiment':
				$this->migrate_headline_experiment( $old_experiment, $new_experiment_id );
				break;

			case 'CssGlobalAlternativeExperiment':
				$this->migrate_css_experiment( $old_experiment, $new_experiment_id );
				break;

			case 'MenuGlobalAlternativeExperiment':
				$this->migrate_menu_experiment( $old_experiment, $new_experiment_id );
				break;

			case 'ThemeGlobalAlternativeExperiment':
				$this->migrate_theme_experiment( $old_experiment, $new_experiment_id );
				break;

			case 'WidgetGlobalAlternativeExperiment':
				$this->migrate_widget_experiment( $old_experiment, $new_experiment_id );
				break;

		}//end switch

		$status = $this->fix_status( $new_experiment_id );
		if ( 'finished' === $status ) {
			$new_experiment = nab_get_experiment( $new_experiment_id );
			$new_experiment->backup_control_version();
			$new_experiment->save();
		}//end if

		$this->migrate_results( $old_experiment, $new_experiment_id );

		wp_delete_post( $old_experiment_id );

	}//end migrate_experiment()

	private function get_old_experiment( $old_experiment_id ) {

		$post = get_post( $old_experiment_id );
		$json = json_decode( urldecode( $post->post_content ), ARRAY_A );

		$json['localExperimentId'] = absint( $old_experiment_id );

		return $json;

	}//end get_old_experiment()

	private function create_new_experiment( $old_experiment ) {

		if ( 'HeatmapExperiment' === $old_experiment['kind'] ) {
			return $this->create_new_heatmap( $old_experiment );
		}//end if

		return $this->create_new_alternative_experiment( $old_experiment );

	}//end create_new_experiment()

	private function create_new_alternative_experiment( $old_experiment ) {

		$new_type       = $this->get_new_type( $old_experiment );
		$new_experiment = Nelio_AB_Testing_Experiment::create_experiment( $new_type );

		$new_experiment->set_name( trim( $old_experiment['name'] ) );
		$new_experiment->set_end_mode_and_value( $this->get_new_end_mode( $old_experiment ), $this->get_new_end_value( $old_experiment ) );
		$new_experiment->set_description( trim( $old_experiment['description'] ) );
		$new_experiment->set_status( $this->get_new_status( $old_experiment ) );

		if ( isset( $old_experiment['start'] ) ) {
			$new_experiment->set_start_date( $old_experiment['start'] );
		}//end if

		if ( isset( $old_experiment['finalization'] ) ) {
			$new_experiment->set_end_date( $old_experiment['finalization'] );
		}//end if

		$goals = $this->create_new_goals( $old_experiment['goals'] );
		$new_experiment->set_goals( $goals );

		$new_experiment->save();
		return $new_experiment->get_id();

	}//end create_new_alternative_experiment()

	private function create_new_heatmap( $old_heatmap ) {

		$new_heatmap = Nelio_AB_Testing_Experiment::create_experiment( 'nab/heatmap' );

		$new_heatmap->set_name( trim( $old_heatmap['name'] ) );
		$new_heatmap->set_end_mode_and_value( $this->get_new_end_mode( $old_heatmap ), $this->get_new_end_value( $old_heatmap ) );
		$new_heatmap->set_description( trim( $old_heatmap['description'] ) );
		$new_heatmap->set_status( $this->get_new_status( $old_heatmap ) );

		if ( isset( $old_heatmap['start'] ) ) {
			$new_heatmap->set_start_date( $old_heatmap['start'] );
		}//end if

		if ( isset( $old_heatmap['finalization'] ) ) {
			$new_heatmap->set_end_date( $old_heatmap['finalization'] );
		}//end if

		$post_id = $old_heatmap['post'];
		if ( $post_id > 0 ) {
			$post = get_post( $post_id );
			if ( $post && ! is_wp_error( $post ) ) {
				$new_heatmap->set_tracking_mode( 'post' );
				$new_heatmap->set_tracked_post_id( $post->ID );
				$new_heatmap->set_tracked_post_type( $post->post_type );
			}//end if
		} else {
			$new_heatmap->set_tracking_mode( 'url' );
			$new_heatmap->set_tracked_url( home_url() );
		}//end if

		$new_heatmap->save();
		return $new_heatmap->get_id();

	}//end create_new_heatmap()

	private function create_new_goals( $old_goals ) {

		if ( ! is_array( $old_goals ) ) {
			$old_goals = array();
		}//end if

		$old_goals = array_filter(
			$old_goals,
			function ( $goal ) {
				if ( isset( $goal['key'] ) && isset( $goal['key']['kind'] ) ) {
					return 'AlternativeExperimentGoal' === $goal['key']['kind'];
				}//end if
				return true;
			}
		);

		return array_map(
			function ( $old_goal ) {
				return $this->create_new_goal( $old_goal );
			},
			array_values( $old_goals )
		);

	}//end create_new_goals()

	private function create_new_goal( $old_goal ) {

		$name       = trim( $old_goal['name'] );
		$name       = preg_replace( '/^Default$|^Unnamed Goal \([0-9]+\)$/', '', $name );
		$attributes = array(
			'name' => $name,
		);

		return array(
			'id'                => nab_uuid(),
			'attributes'        => $attributes,
			'conversionActions' => $this->migrate_conversion_actions( $old_goal ),
		);

	}//end create_new_goal()

	private function migrate_conversion_actions( $old_goal ) {

		$conversion_actions = array();

		if ( ! is_array( $old_goal['pageAccessedActions'] ) ) {
			$old_goal['pageAccessedActions'] = array();
		}//end if
		foreach ( $old_goal['pageAccessedActions'] as $old_action ) {
			$new_action           = $this->create_empty_conversion_action();
			$new_action           = $this->migrate_page_accessed_action( $old_action, $new_action );
			$new_action['_order'] = absint( $old_action['order'] );
			array_push( $conversion_actions, $new_action );
		}//end foreach

		if ( ! is_array( $old_goal['formActions'] ) ) {
			$old_goal['formActions'] = array();
		}//end if
		foreach ( $old_goal['formActions'] as $old_action ) {
			$new_action           = $this->create_empty_conversion_action();
			$new_action           = $this->migrate_form_action( $old_action, $new_action );
			$new_action['_order'] = absint( $old_action['order'] );
			array_push( $conversion_actions, $new_action );
		}//end foreach

		if ( ! is_array( $old_goal['clickActions'] ) ) {
			$old_goal['clickActions'] = array();
		}//end if
		foreach ( $old_goal['clickActions'] as $old_action ) {
			$new_action           = $this->create_empty_conversion_action();
			$new_action           = $this->migrate_click_action( $old_action, $new_action );
			$new_action['_order'] = absint( $old_action['order'] );
			array_push( $conversion_actions, $new_action );
		}//end foreach

		if ( ! is_array( $old_goal['orderCompletedActions'] ) ) {
			$old_goal['orderCompletedActions'] = array();
		}//end if
		foreach ( $old_goal['orderCompletedActions'] as $old_action ) {
			$new_action           = $this->create_empty_conversion_action();
			$new_action           = $this->migrate_order_completed_action( $old_action, $new_action );
			$new_action['_order'] = absint( $old_action['order'] );
			array_push( $conversion_actions, $new_action );
		}//end foreach

		uasort(
			$conversion_actions,
			function( $a, $b ) {
				if ( $a['_order'] === $b['_order'] ) {
					return 0;
				}//end if
				return ( $a['_order'] < $b['_order'] ) ? -1 : 1;
			}
		);

		return array_map(
			function ( $action ) {
				unset( $action['_order'] );
				return $action;
			},
			$conversion_actions
		);

	}//end migrate_conversion_actions()

	private function create_empty_conversion_action() {

		return array(
			'id'         => nab_uuid(),
			'type'       => '',
			'attributes' => array(),
		);

	}//end create_empty_conversion_action()

	private function migrate_page_accessed_action( $old_action, $new_action ) {

		if ( ! $old_action['internal'] ) {

			$new_action['type'] = 'nab/click-external-link';

			$link = trim( $old_action['reference'] );
			if ( preg_match( '/^\*\*\*/', $link ) && preg_match( '/\*\*\*$/', $link ) ) {
				$new_action['attributes']['mode']  = 'exact';
				$new_action['attributes']['value'] = substr( $link, 3, strlen( $link ) - 6 );
				return $new_action;
			} elseif ( preg_match( '/\*\*\*$/', $link ) ) {
				$new_action['attributes']['mode']  = 'start';
				$new_action['attributes']['value'] = substr( $link, 0, strlen( $link ) - 3 );
			} elseif ( preg_match( '/^\*\*\*/', $link ) ) {
				$new_action['attributes']['mode']  = 'end';
				$new_action['attributes']['value'] = substr( $link, 3 );
			} else {
				$new_action['attributes']['mode']  = 'exact';
				$new_action['attributes']['value'] = $link;
			}//end if

			return $new_action;

		}//end if

		$new_action['type'] = 'nab/page-view';

		$new_action['attributes']['postType'] = 'page';
		$new_action['attributes']['postId']   = 0;

		$post_id = intval( $old_action['reference'] );
		if ( 0 >= $post_id ) {
			return $new_action;
		}//end if

		$post = get_post( $post_id );
		if ( ! $post || is_wp_error( $post ) ) {
			return $post;
		}//end if

		$new_action['attributes']['postType'] = $post->post_type;
		$new_action['attributes']['postId']   = $post->ID;
		return $new_action;

	}//end migrate_page_accessed_action()

	private function migrate_form_action( $old_action, $new_action ) {

		$new_action['type'] = 'nab/form-submission';

		$new_action['attributes']['formType'] = false;
		$new_action['attributes']['formId']   = 0;

		if ( 'contact-form-7' === $old_action['plugin'] ) {
			$new_action['attributes']['formType'] = 'wpcf7_contact_form';
			$new_action['attributes']['formId']   = absint( $old_action['form'] );
		} elseif ( 'gravity-form' === $old_action['plugin'] ) {
			$new_action['attributes']['formType'] = 'nab_gravity_form';
			$new_action['attributes']['formId']   = absint( $old_action['form'] );
		}//end if

		return $new_action;

	}//end migrate_form_action()

	private function migrate_click_action( $old_action, $new_action ) {

		$new_action['type'] = 'nab/click';

		$new_action['attributes']['mode']  = 'css';
		$new_action['attributes']['value'] = '';

		if ( 'css-path' === $old_action['kind'] ) {
			$new_action['attributes']['mode']  = 'css';
			$new_action['attributes']['value'] = trim( $old_action['value'] );
		} elseif ( 'id' === $old_action['kind'] ) {
			$new_action['attributes']['mode']  = 'id';
			$new_action['attributes']['value'] = trim( $old_action['value'] );
		} elseif ( 'text-is' === $old_action['kind'] ) {
			$new_action['attributes']['mode']  = 'css';
			$new_action['attributes']['value'] = '?' . _x( 'Invalid', 'text (as in “invalid value”)', 'nelio-ab-testing' );
		}//end if

		return $new_action;

	}//end migrate_click_action()

	private function migrate_order_completed_action( $old_action, $new_action ) {

		$new_action['type'] = 'nab/wc-order';

		$new_action['attributes']['anyProduct'] = false;
		$new_action['attributes']['productId']  = absint( $old_action['product'] );

		return $new_action;

	}//end migrate_order_completed_action()

	private function migrate_post_experiment( $old_experiment, $new_experiment_id ) {

		$control = array(
			'id'         => 'control',
			'attributes' => array(
				'postType' => $old_experiment['postType'],
				'postId'   => absint( $old_experiment['originalPost'] ),
			),
		);

		$alternatives = array( $control );
		foreach ( $old_experiment['alternatives'] as $old_alternative ) {
			array_push(
				$alternatives,
				array(
					'id'         => nab_uuid(),
					'attributes' => array(
						'name'     => $old_alternative['name'],
						'postType' => $old_experiment['postType'],
						'postId'   => $this->migrate_alternative_post( absint( $old_alternative['value'] ), $new_experiment_id ),
					),
				)
			);
		}//end foreach
		update_post_meta( $new_experiment_id, '_nab_alternatives', $alternatives );

	}//end migrate_post_experiment()

	private function migrate_alternative_post( $post_id, $new_experiment_id ) {

		delete_post_meta( $post_id, '_is_nelioab_alternative' );
		delete_post_meta( $post_id, '_nelioab_original_id' );
		delete_post_meta( $post_id, '_nelioab_hide_discussion' );

		$post = get_post( $post_id );
		if ( empty( $post ) || is_wp_error( $post ) ) {
			return $post_id;
		}//end if

		$post_type = str_replace( 'nelioab_alt_', '', $post->post_type );
		wp_update_post(
			array(
				'ID'        => $post_id,
				'post_type' => $post_type,
			)
		);

		update_post_meta( $post_id, '_nab_experiment', $new_experiment_id );

		return $post_id;

	}//end migrate_alternative_post()

	private function migrate_headline_experiment( $old_experiment, $new_experiment_id ) {

		$control = array(
			'id'         => 'control',
			'attributes' => array(
				'postType' => 'WC_ProductSummaryAlternativeExperiment' === $old_experiment['kind'] ? 'product' : 'post',
				'postId'   => absint( $old_experiment['originalPost'] ),
			),
		);

		$alternatives = array( $control );
		foreach ( $old_experiment['alternatives'] as $old_alternative ) {
			$values = json_decode( $old_alternative['value'], ARRAY_A );
			array_push(
				$alternatives,
				array(
					'id'         => nab_uuid(),
					'attributes' => array(
						'name'    => $old_alternative['name'],
						'excerpt' => trim( $values['excerpt'] ),
						'imageId' => absint( $values['image_id'] ),
					),
				)
			);
		}//end foreach
		update_post_meta( $new_experiment_id, '_nab_alternatives', $alternatives );

		$goal = array(
			'id'                => nab_uuid(),
			'attributes'        => array(),
			'conversionActions' => array(
				array(
					'id'         => nab_uuid(),
					'type'       => 'nab/page-view',
					'attributes' => $alternatives[0]['attributes'],
				),
			),
		);
		update_post_meta( $new_experiment_id, '_nab_goals', array( $goal ) );

	}//end migrate_headline_experiment()

	private function migrate_css_experiment( $old_experiment, $new_experiment_id ) {

		$old_alternatives = $old_experiment['alternatives'];

		$control = array_shift( $old_alternatives );
		$control = array(
			'id'         => 'control',
			'attributes' => array(),
		);

		$alternatives = array( $control );
		foreach ( $old_alternatives as $old_alternative ) {
			array_push(
				$alternatives,
				array(
					'id'         => nab_uuid(),
					'attributes' => array(
						'name' => $old_alternative['name'],
						'css'  => $old_alternative['content'],
					),
				)
			);
		}//end foreach
		update_post_meta( $new_experiment_id, '_nab_alternatives', $alternatives );

	}//end migrate_css_experiment()

	private function migrate_menu_experiment( $old_experiment, $new_experiment_id ) {

		$old_alternatives = $old_experiment['alternatives'];

		$control = array_shift( $old_alternatives );
		$control = array(
			'id'         => 'control',
			'attributes' => array(
				'menuId' => absint( $control['value'] ),
			),
		);

		$alternatives = array( $control );
		foreach ( $old_alternatives as $old_alternative ) {
			array_push(
				$alternatives,
				array(
					'id'         => nab_uuid(),
					'attributes' => array(
						'name' => $old_alternative['name'],
						'menu' => $this->migrate_alternative_menu( $old_alternative['value'], $new_experiment_id ),
					),
				)
			);
		}//end foreach
		update_post_meta( $new_experiment_id, '_nab_alternatives', $alternatives );

	}//end migrate_menu_experiment()

	private function migrate_alternative_menu( $menu_id, $new_experiment_id ) {

		update_term_meta( $menu_id, '_nab_experiment', $new_experiment_id );
		return $menu_id;

	}//end migrate_alternative_menu()

	private function migrate_theme_experiment( $old_experiment, $new_experiment_id ) {

		$old_alternatives = $old_experiment['alternatives'];

		$control = array_shift( $old_alternatives );
		$control = array(
			'id'         => 'control',
			'attributes' => array(),
		);

		$alternatives = array( $control );
		foreach ( $old_alternatives as $old_alternative ) {

			$theme_id   = $old_alternative['value'];
			$theme_name = $theme_id;

			$theme = wp_get_theme( $theme_id );
			if ( $theme && ! is_wp_error( $theme ) ) {
				$theme_name = $theme['Name'];
			}//end if

			array_push(
				$alternatives,
				array(
					'id'         => nab_uuid(),
					'attributes' => array(
						'name'    => $theme_name,
						'themeId' => $theme_id,
					),
				)
			);

		}//end foreach
		update_post_meta( $new_experiment_id, '_nab_alternatives', $alternatives );

	}//end migrate_theme_experiment()

	private function migrate_widget_experiment( $old_experiment, $new_experiment_id ) {

		$old_alternatives = $old_experiment['alternatives'];

		$control = array_shift( $old_alternatives );
		$control = array(
			'id'         => 'control',
			'attributes' => array(),
		);

		$alternatives = array( $control );
		foreach ( $old_alternatives as $old_alternative ) {

			$theme_id   = $old_alternative['value'];
			$theme_name = $theme_id;

			$theme = wp_get_theme( $theme_id );
			if ( $theme && ! is_wp_error( $theme ) ) {
				$theme_name = $theme['Name'];
			}//end if

			$new_alternative_id = nab_uuid();
			$new_alternative    = array(
				'id'         => $new_alternative_id,
				'attributes' => array(
					'sidebars' => $this->create_alternative_sidebars( $new_experiment_id, $new_alternative_id ),
				),
			);
			array_push( $alternatives, $new_alternative );

			$old_experiment_id  = $old_experiment['localExperimentId'];
			$old_alternative_id = $old_alternative['key']['id'];
			$this->move_alternative_widgets_to_new_sidebars( $old_experiment_id, $old_alternative_id, $new_experiment_id, $new_alternative_id );

		}//end foreach
		update_post_meta( $new_experiment_id, '_nab_alternatives', $alternatives );

	}//end migrate_widget_experiment()

	private function create_alternative_sidebars( $new_experiment_id, $new_alternative_id ) {

		$control_sidebars = \Nelio_AB_Testing\Experiment_Library\Widget_Experiment\get_control_sidebars();
		$sidebar_prefix   = \Nelio_AB_Testing\Experiment_Library\Widget_Experiment\get_sidebar_prefix( $new_experiment_id, $new_alternative_id );

		return array_map(
			function ( $sidebar ) use ( $sidebar_prefix ) {
				return array(
					'id'      => "$sidebar_prefix$sidebar",
					'control' => $sidebar,
				);
			},
			$control_sidebars
		);

	}//end create_alternative_sidebars()

	private function move_alternative_widgets_to_new_sidebars( $old_experiment_id, $old_alternative_id, $new_experiment_id, $new_alternative_id ) {

		$old_alternative_widgets = get_option( 'nelioab_widgets_in_experiments' );

		$relevant_widgets = array();
		foreach ( $old_alternative_widgets as $widget => $meta ) {
			if ( absint( $meta['exp'] ) !== $old_experiment_id || $meta['alt'] !== $old_alternative_id ) {
				continue;
			}//end if
			array_push( $relevant_widgets, $widget );
		}//end foreach

		$sidebar_prefix   = \Nelio_AB_Testing\Experiment_Library\Widget_Experiment\get_sidebar_prefix( $new_experiment_id, $new_alternative_id );
		$sidebars_widgets = get_option( 'sidebars_widgets' );

		$sidebars = array_keys( $sidebars_widgets );
		foreach ( $sidebars as $sidebar ) {

			if ( 'wp_inactive_widgets' === $sidebar ) {
				continue;
			}//end if

			$widgets = $sidebars_widgets[ $sidebar ];
			if ( ! is_array( $widgets ) || empty( $widgets ) ) {
				continue;
			}//end if

			$new_sidebar = "$sidebar_prefix$sidebar";

			$sidebars_widgets[ $new_sidebar ] = array();
			foreach ( $widgets as $widget ) {
				if ( in_array( $widget, $relevant_widgets, true ) ) {
					array_push( $sidebars_widgets[ $new_sidebar ], $widget );
				}//end if
			}//end foreach

			$sidebars_widgets[ $sidebar ] = array_values( array_diff( $sidebars_widgets[ $sidebar ], $sidebars_widgets[ $new_sidebar ] ) );

		}//end foreach

		update_option( 'sidebars_widgets', $sidebars_widgets );

	}//end move_alternative_widgets_to_new_sidebars()

	private function get_new_status( $old_experiment ) {

		if ( ! isset( $old_experiment['status'] ) ) {
			return 'draft';
		}//end if

		switch ( absint( $old_experiment['status'] ) ) {

			case 1:
				return 'draft';

			case 2:
				return 'paused';

			case 3:
				return 'ready';

			case 4:
				return 'running';

			case 5:
				return 'finished';

			case 6:
				return 'trash';

			case 7:
				return 'scheduled';

			default:
				return 'draft';

		}//end switch

	}//end get_new_status()

	private function fix_status( $new_experiment_id ) {

		$new_experiment = nab_get_experiment( $new_experiment_id );

		$current_status = $new_experiment->get_status();
		if ( 'finished' === $current_status ) {
			return 'finished';
		}//end if

		if ( 'running' === $current_status ) {
			return 'running';
		}//end if

		if ( empty( $new_experiment->get_name() ) ) {
			$new_experiment->set_status( 'draft' );
			$new_experiment->save();
			return 'draft';
		}//end if

		if ( 'nab/heatmap' === $new_experiment->get_type() ) {
			return $this->fix_heatmap_status( $new_experiment );
		} else {
			return $this->fix_alternative_experiment_status( $new_experiment );
		}//end if

	}//end fix_status()

	private function fix_heatmap_status( $heatmap ) {

		if ( 'url' === $heatmap->get_tracking_mode() ) {
			$url = $heatmap->get_tracked_url();
			if ( empty( $url ) ) {
				$heatmap->set_status( 'draft' );
			}//end if
			$heatmap->save();
			return $heatmap->get_status();
		}//end if

		$post_id = $heatmap->get_tracked_post_id();
		if ( empty( $post_id ) ) {
			$heatmap->set_status( 'draft' );
			$heatmap->save();
			return $heatmap->get_status();
		}//end if

		$post = get_post( $post_id );
		if ( ! $post || is_wp_error( $post ) ) {
			$heatmap->set_status( 'draft' );
			$heatmap->save();
			return $heatmap->get_status();
		}//end if

		if ( 'draft' === $heatmap->get_status() ) {
			$heatmap->set_status( 'ready' );
			$heatmap->save();
			return $heatmap->get_status();
		}//end if

		return $heatmap->get_status();

	}//end fix_heatmap_status()

	private function fix_alternative_experiment_status( $new_experiment ) {

		if ( $this->is_there_an_incomplete_goal( $new_experiment->get_goals() ) ) {
			$new_experiment->set_status( 'draft' );
		} elseif ( $this->is_control_invalid( $new_experiment ) ) {
			$new_experiment->set_status( 'draft' );
		} elseif ( $this->is_there_an_incomplete_alternative( $new_experiment->get_alternatives() ) ) {
			$new_experiment->set_status( 'draft' );
		}//end if

		$new_experiment->save();
		return $new_experiment->get_status();

	}//end fix_alternative_experiment_status()

	private function is_there_an_incomplete_goal( $goals ) {

		foreach ( $goals as $goal ) {

			if ( empty( $goal['conversionActions'] ) ) {
				return true;
			}//end if

			foreach ( $goal['conversionActions'] as $action ) {
				if ( $this->has_empty_attributes( $action ) ) {
					return true;
				}//end if
			}//end foreach
		}//end foreach

		return false;

	}//end is_there_an_incomplete_goal()

	private function is_there_an_incomplete_alternative( $alternatives ) {

		foreach ( $alternatives as $alternative ) {
			if ( 'control' === $alternative['id'] ) {
				continue;
			}//end if
			if ( isset( $alternative['attributes']['postType'] ) && empty( $alternative['attributes']['postType'] ) ) {
				return true;
			}//end if
			if ( isset( $alternative['attributes']['postId'] ) && empty( $alternative['attributes']['postId'] ) ) {
				return true;
			}//end if
		}//end foreach

		return false;

	}//end is_there_an_incomplete_alternative()

	private function is_control_invalid( $new_experiment ) {

		if ( in_array( $new_experiment->get_type(), array( 'nab/css', 'nab/theme', 'nab/widget' ), true ) ) {
			return false;
		}//end if

		$control = $new_experiment->get_alternative( 'control' );
		return $this->has_empty_attributes( $control );

	}//end is_control_invalid()

	private function has_empty_attributes( $object ) {

		if ( empty( $object['attributes'] ) ) {
			return true;
		}//end if

		$values = array_values( $object['attributes'] );
		foreach ( $values as $value ) {
			if ( empty( $value ) ) {
				return true;
			}//end if
		}//end foreach

		return false;

	}//end has_empty_attributes()

	private function get_new_type( $old_experiment ) {

		switch ( $old_experiment['kind'] ) {

			case 'PageAlternativeExperiment':
				return 'nab/page';

			case 'PostAlternativeExperiment':
				return 'nab/post';

			case 'CptAlternativeExperiment':
				return 'nab/custom-post-type';

			case 'HeadlineAlternativeExperiment':
				return 'nab/headline';

			case 'WC_ProductSummaryAlternativeExperiment':
				return 'nab/product-summary';

			case 'CssGlobalAlternativeExperiment':
				return 'nab/css';

			case 'MenuGlobalAlternativeExperiment':
				return 'nab/menu';

			case 'ThemeGlobalAlternativeExperiment':
				return 'nab/theme';

			case 'WidgetGlobalAlternativeExperiment':
				return 'nab/widget';

			case 'HeatmapExperiment':
				return 'nab/heatmap';

			default:
				return false;

		}//end switch

	}//end get_new_type()

	private function get_new_end_mode( $old_experiment ) {

		if ( ! isset( $old_experiment['finalizationMode'] ) ) {
			return 'draft';
		}//end if

		switch ( absint( $old_experiment['finalizationMode'] ) ) {

			case 0:
				return 'manual';

			case 1:
				return 'page-views';

			case 2:
				return 'confidence';

			case 3:
				return 'duration';

			default:
				return 'manual';

		}//end switch

	}//end get_new_end_mode()

	private function get_new_end_value( $old_experiment ) {

		$end_mode  = $this->get_new_end_mode( $old_experiment );
		$end_value = absint( $old_experiment['finalizationModeValue'] );

		switch ( $end_mode ) {

			case 'page-views':
				return $this->get_closest_value( $end_value, array( 2500, 5000, 10000, 20000, 50000, 100000 ) );

			case 'confidence':
				return $this->get_closest_value( $end_value, array( 95, 96, 97, 98, 99 ) );

			case 'duration':
				return $this->get_closest_value( $end_value, array( 5, 7, 15, 30, 60, 90 ) );

			default:
				return 0;

		}//end switch

	}//end get_new_end_value()

	private function get_closest_value( $needle, $haystack ) {

		$closest_value = $haystack[0];
		foreach ( $haystack as $candidate ) {
			if ( abs( $candidate - $needle ) < abs( $candidate - $closest_value ) ) {
				$closest_value = $candidate;
			}//end if
		}//end foreach
		return $closest_value;

	}//end get_closest_value()

	private function migrate_results( $old_experiment, $new_experiment_id ) {

		$new_experiment = nab_get_experiment( $new_experiment_id );
		if ( ! in_array( $new_experiment->get_status(), array( 'finished', 'running' ), true ) ) {
			return;
		}//end if

		$params = array(
			'credentials'    => $this->create_old_account_credentials(),
			'experimentKey'  => $old_experiment['key'],
			'alternativeIds' => $this->get_alternative_ids( $old_experiment ),
			'goalIds'        => $this->get_alternative_goal_ids( $old_experiment ),
			'status'         => $new_experiment->get_status(),
			'migrateClicks'  => $this->should_clicks_be_migrated( $new_experiment ),
			'ttl'            => $this->get_ttl_in_days_for_click_events( $new_experiment ),
			'startDate'      => $new_experiment->get_start_date(),
			'endDate'        => $new_experiment->get_end_date(),
			'timezone'       => nab_get_timezone(),
		);

		$backup = array(
			'params' => $params,
			'meta'   => array(
				'newSiteId'       => nab_get_site_id(),
				'newExperimentId' => $new_experiment_id,
				'timestamp'       => gmdate( 'c' ),
			),
		);
		update_post_meta( $new_experiment_id, '_nab_result_migration_params', $backup );

		$data = array(
			'method'    => 'POST',
			'timeout'   => apply_filters( 'nab_migration_request_timeout', 120 ),
			'sslverify' => ! nab_does_api_use_proxy(),
			'body'      => wp_json_encode( $params ),
			'headers'   => array(
				'Authorization' => 'Bearer ' . nab_generate_api_auth_token(),
				'accept'        => 'application/json',
				'content-type'  => 'application/json',
			),
		);

		$url      = nab_get_api_url( '/site/' . nab_get_site_id() . '/migrate/' . $new_experiment_id, 'wp' );
		$response = wp_remote_request( $url, $data );

		$error = nab_maybe_return_error_json( $response );
		if ( $error ) {
			return;
		}//end if

		$results = json_decode( $response['body'], true );
		update_post_meta( $new_experiment_id, '_nab_timeline_results', $results );
		if ( 'finished' === $new_experiment->get_status() ) {
			update_post_meta( $new_experiment_id, '_nab_are_timeline_results_definitive', true );
		} else {
			delete_post_meta( $new_experiment_id, '_nab_are_timeline_results_definitive' );
		}//end if

	}//end migrate_results()

	private function create_old_account_credentials() {

		$account     = get_option( 'nelioab_account_settings', array() );
		$credentials = array();

		$fields = array(
			array( 'customer_id', 'customerId' ),
			array( 'reg_num', 'registrationNumber' ),
			array( 'site_id', 'siteId' ),
		);
		foreach ( $fields as $pair ) {
			$from = $pair[0];
			$to   = $pair[1];
			if ( isset( $account[ $from ] ) && ! empty( $account[ $from ] ) ) {
				$credentials[ $to ] = $account[ $from ];
			}//end if
		}//end foreach

		$credentials['siteUrl'] = get_option( 'siteurl' );

		return $credentials;

	}//end create_old_account_credentials()

	private function get_alternative_ids( $old_experiment ) {

		if ( 'HeatmapExperiment' === $old_experiment['kind'] ) {
			return array();
		}//end if

		if ( ! in_array( $old_experiment['kind'], array( 'PageAlternativeExperiment', 'PostAlternativeExperiment', 'CptAlternativeExperiment' ), true ) ) {
			return array();
		}//end if

		$original     = absint( $old_experiment['originalPost'] );
		$alternatives = array_map(
			function ( $alternative ) {
				return absint( $alternative['value'] );
			},
			$old_experiment['alternatives']
		);

		return array_merge( array( $original ), $alternatives );

	}//end get_alternative_ids()

	private function get_alternative_goal_ids( $old_experiment ) {

		if ( 'HeatmapExperiment' === $old_experiment['kind'] ) {
			return array();
		}//end if

		$goals = array_filter(
			$old_experiment['goals'],
			function ( $goal ) {
				return 'AlternativeExperimentGoal' === $goal['key']['kind'];
			}
		);

		return array_map(
			function ( $goal ) {
				return $goal['key']['id'];
			},
			array_values( $goals )
		);

	}//end get_alternative_goal_ids()

	private function should_clicks_be_migrated( $new_experiment ) {

		if ( ! in_array( $new_experiment->get_type(), array( 'nab/heatmap', 'nab/page', 'nab/post', 'nab/custom-post-type' ), true ) ) {
			return false;
		}//end if

		if ( ! in_array( $new_experiment->get_status(), array( 'running', 'finished', 'paused', 'paused_draft' ), true ) ) {
			return false;
		}//end if

		if ( 'finished' === $new_experiment->get_status() ) {
			$ttl = $this->get_ttl_in_days_for_click_events( $new_experiment );
			return ! empty( $ttl );
		}//end if

		return true;

	}//end should_clicks_be_migrated()

	private function get_ttl_in_days_for_click_events( $new_experiment ) {

		$max_ttl = 900;
		if ( in_array( $new_experiment->get_status(), array( 'running', 'paused', 'paused_draft' ), true ) ) {
			return $max_ttl;
		}//end if

		$end_date    = strtotime( $new_experiment->get_end_date() );
		$today       = time();
		$age_in_days = max( 0, $today - $end_date ) / DAY_IN_SECONDS;
		return floor( max( 0, 900 - $age_in_days ) );

	}//end get_ttl_in_days_for_click_events()

	private function is_old_experiment( $eid ) {
		return 'nelioab_local_exp' === get_post_type( $eid );
	}//end is_old_experiment()
}//end class
