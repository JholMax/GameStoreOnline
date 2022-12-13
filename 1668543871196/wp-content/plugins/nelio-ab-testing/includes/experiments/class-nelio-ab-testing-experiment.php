<?php
/**
 * This file defines the class of a Nelio A/B Testing Experiment.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/utils/experiments
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * An Experiment in Nelio A/B Testing.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/utils/experiments
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      5.0.0
 */
class Nelio_AB_Testing_Experiment {

	/**
	 * The experiment (post) ID.
	 *
	 * @var int
	 */
	public $ID = 0;

	/**
	 * Stores post data.
	 *
	 * @var WP_Post
	 */
	public $post = null;

	/**
	 * Stores the experiment type.
	 *
	 * @var string
	 */
	private $type = null;

	/**
	 * The UTC date in which the experiment started (or will start, if it's scheduled).
	 *
	 * @var boolean|string
	 */
	private $start_date = false;

	/**
	 * The UTC date in which the experiment ended.
	 *
	 * @var boolean|string
	 */
	private $end_date = false;

	/**
	 * How the experiment should end.
	 *
	 * @var string
	 */
	private $end_mode = 'manual';

	/**
	 * The starter of the experiment.
	 *
	 * @var boolean|string|integer
	 */
	private $starter = false;

	/**
	 * The stopper of the experiment.
	 *
	 * @var boolean|string|integer
	 */
	private $stopper = false;

	/**
	 * If the end mode is other than manual, this value specifies the concrete
	 * value at which the experiment should end.
	 *
	 * @var array
	 */
	private $end_value = 0;

	/**
	 * List of alternatives.
	 *
	 * @var array
	 */
	private $alternatives = array();

	/**
	 * List of goals.
	 *
	 * @var array
	 */
	private $goals = array();

	/**
	 * List of segments.
	 *
	 * @var array
	 */
	private $segments = array();

	/**
	 * List of pairs type/value with the URLs where the test should run.
	 *
	 * @var array
	 */
	private $scope = array();

	/**
	 * Backup of the control version.
	 *
	 * @var array
	 */
	private $control_backup = false;

	/**
	 * Alternative applied (if any).
	 *
	 * @var array
	 */
	private $last_alternative_applied = false;

	/**
	 * Creates a new instance of this class.
	 *
	 * @param integer|WP_Post $experiment The identifier of an experiment
	 *            in the database, or a WP_Post instance with it.
	 *
	 * @since  5.0.0
	 * @access protected
	 */
	protected function __construct( $experiment ) {

		if ( is_numeric( $experiment ) ) {
			$experiment = get_post( $experiment );
		}//end if

		if ( isset( $experiment->ID ) ) {

			$this->ID   = absint( $experiment->ID );
			$this->post = $experiment;
			$this->type = get_post_meta( $this->ID, '_nab_experiment_type', true );

			$start_date       = get_post_meta( $this->ID, '_nab_start_date', true );
			$this->start_date = ! empty( $start_date ) ? $start_date : false;
			$end_date         = get_post_meta( $this->ID, '_nab_end_date', true );
			$this->end_date   = ! empty( $end_date ) ? $end_date : false;
			$this->end_mode   = get_post_meta( $this->ID, '_nab_end_mode', true );
			$this->end_value  = get_post_meta( $this->ID, '_nab_end_value', true );

			$this->alternatives = get_post_meta( $this->ID, '_nab_alternatives', true );
			$this->goals        = get_post_meta( $this->ID, '_nab_goals', true );
			$this->segments     = get_post_meta( $this->ID, '_nab_segments', true );

			$this->scope = get_post_meta( $this->ID, '_nab_scope', true );

			$starter       = get_post_meta( $this->ID, '_nab_starter', true );
			$this->starter = ! empty( $starter ) ? $starter : false;
			$stopper       = get_post_meta( $this->ID, '_nab_stopper', true );
			$this->stopper = ! empty( $stopper ) ? $stopper : false;

			$control_backup       = get_post_meta( $this->ID, '_nab_control_backup', true );
			$this->control_backup = ! empty( $control_backup ) ? $control_backup : false;

			$last_alt_applied               = get_post_meta( $this->ID, '_nab_last_alternative_applied', true );
			$this->last_alternative_applied = ! empty( $last_alt_applied ) ? $last_alt_applied : false;

		}//end if

		if ( empty( $this->end_mode ) ) {
			$this->end_mode  = 'manual';
			$this->end_value = 0;
		}//end if

		$this->end_value = absint( $this->end_value );

	}//end __construct()

	public static function get_experiment( $experiment ) {

		if ( is_numeric( $experiment ) ) {
			$experiment_id = $experiment;
		} elseif ( isset( $experiment->ID ) ) {
			$experiment_id = absint( $experiment->ID );
		}//end if

		if ( ! $experiment_id ) {
			return new WP_Error( 'experiment-id-not-found', _x( 'Test not found.', 'text', 'nelio-ab-testing' ) );
		}//end if

		$experiment = get_post( $experiment_id );
		if ( empty( $experiment ) ) {
			return new WP_Error( 'experiment-id-not-found', _x( 'Test not found.', 'text', 'nelio-ab-testing' ) );
		}//end if

		if ( 'nab_experiment' !== $experiment->post_type ) {
			return new WP_Error( 'invalid-experiment', _x( 'Invalid test.', 'text', 'nelio-ab-testing' ) );
		}//end if

		$experiment_type = get_post_meta( $experiment->ID, '_nab_experiment_type', true );
		if ( empty( $experiment_type ) ) {
			return new WP_Error( 'invalid-experiment', _x( 'Invalid test.', 'text', 'nelio-ab-testing' ) );
		}//end if

		if ( 'nab/heatmap' === $experiment_type ) {
			return new Nelio_AB_Testing_Heatmap( $experiment );
		}//end if

		return new Nelio_AB_Testing_Experiment( $experiment );

	}//end get_experiment()

	/**
	 * Creates a new experiment of the given type and returns it.
	 *
	 * @param string $experiment_type the experiment type.
	 *
	 * @return Nelio_AB_Testing_Experiment|WP_Error Experiment object or an error
	 *            if the experiment couldn't be created.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public static function create_experiment( $experiment_type ) {

		$post_id = wp_insert_post(
			array(
				'post_type'   => 'nab_experiment',
				'post_status' => 'draft',
			)
		);

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}//end if

		update_post_meta( $post_id, '_nab_experiment_type', $experiment_type );

		return self::get_experiment( $post_id );

	}//end create_experiment()

	/**
	 * Returns the ID of this experiment.
	 *
	 * @return integer the ID of this experiment.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function get_id() {

		return $this->ID;

	}//end get_id()

	/**
	 * Returns the tested element ID of this experiment.
	 *
	 * @return mixed the tested element ID of this experiment. False if unknown.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function get_tested_element() {

		$control         = $this->get_alternative( 'control' );
		$experiment_type = $this->get_type();

		/**
		 * Returns the tested element ID of this experiment.
		 *
		 * @param mixed $tested_element tested element ID of this experiment. Default: `false`.
		 * @param array $control        original alternative.
		 *
		 * @since 5.0.0
		 */
		return apply_filters( "nab_{$experiment_type}_get_tested_element", false, $control['attributes'] );

	}//end get_tested_element()

	/**
	 * Returns the type of this experiment.
	 *
	 * @return string the type of this experiment.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function get_type() {

		return $this->type;

	}//end get_type()

	/**
	 * Returns the WordPress post of this experiment.
	 *
	 * @return WP_Post the post of this experiment.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function get_post() {

		return $this->post;

	}//end get_post()

	/**
	 * Returns the name of this experiment.
	 *
	 * @return string the name of this experiment.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function get_name() {

		return $this->post->post_title;

	}//end get_name()

	/**
	 * Sets the name of this experiment.
	 *
	 * @param string $name the name of this experiment.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function set_name( $name ) {

		$this->post->post_title = $name;

	}//end set_name()

	/**
	 * Returns whether the experiment can be edited or not.
	 *
	 * @return boolean whether the experiment can be edited or not.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function can_be_edited() {

		return in_array( $this->post->post_status, array( 'draft', 'nab_ready', 'nab_scheduled', 'nab_paused', 'nab_paused_draft' ), true );

	}//end can_be_edited()

	/**
	 * Returns whether the experiment can be started or not.
	 *
	 * @return boolean|WP_Error whether the experiment can be started or not. If it can’t, it returns an error with the explanation.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function can_be_started() {

		$helper = Nelio_AB_Testing_Experiment_Helper::instance();

		if ( 'running' === $this->get_status() ) {
			return new WP_Error(
				'experiment-already-running',
				sprintf(
					/* translators: 1 -> experiment name */
					_x( 'Test %1$s is already running.', 'text', 'nelio-ab-testing' ),
					$helper->get_non_empty_name( $this )
				)
			);
		}//end if

		if ( ! in_array( $this->get_status(), array( 'ready', 'scheduled' ), true ) ) {
			return new WP_Error(
				'experiment-cannot-be-started-due-to-invalid-status',
				sprintf(
					/* translators: 1 -> experiment name, 2 -> experiment status */
					_x( 'Test %1$s can’t be started because its status is “%2$s.”', 'text', 'nelio-ab-testing' ),
					$helper->get_non_empty_name( $this ),
					$this->get_status()
				)
			);
		}//end if

		if ( ! nab_is_subscribed() ) {
			if ( ! empty( nab_get_running_experiment_ids() ) || ! empty( nab_get_running_experiment_ids() ) ) {
				return new WP_Error(
					'experiments-already-running-in-free',
					esc_html_x( 'There’s another test currently running. Subscribe to Nelio A/B Testing Premium to run more than one test at a time.', 'user', 'nelio-ab-testing' )
				);
			}//end if
		}//end if

		$running_experiment = $helper->does_overlap_with_running_experiment( $this );
		if ( ! empty( $running_experiment ) ) {
			return new WP_Error(
				'equivalent-experiment-running',
				sprintf(
					/* translators: 1 -> one experiment name, 2 -> another experiment name */
					_x( 'Test %1$s can’t be started because there’s another running test that’s testing the same element(s): %2$s.', 'text', 'nelio-ab-testing' ),
					$helper->get_non_empty_name( $this ),
					$helper->get_non_empty_name( $running_experiment )
				)
			);
		}//end if

		return true;

	}//end can_be_started()

	/**
	 * Returns whether the experiment can be resumed or not.
	 *
	 * @return boolean|WP_Error whether the experiment can be resumed or not. If it can’t, it returns an error with the explanation.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function can_be_resumed() {

		$helper = Nelio_AB_Testing_Experiment_Helper::instance();

		if ( 'running' === $this->get_status() ) {
			return new WP_Error(
				'experiment-already-running',
				sprintf(
					/* translators: 1 -> experiment name */
					_x( 'Test %1$s is already running.', 'text', 'nelio-ab-testing' ),
					$helper->get_non_empty_name( $this )
				)
			);
		}//end if

		if ( 'paused' !== $this->get_status() ) {
			return new WP_Error(
				'experiment-cannot-be-resumed',
				sprintf(
					/* translators: 1 -> experiment name, 2 -> experiment status */
					_x( 'Test %1$s can’t be resumed because its status is “%2$s.”', 'text', 'nelio-ab-testing' ),
					$helper->get_non_empty_name( $this ),
					$this->get_status()
				)
			);
		}//end if

		if ( ! nab_is_subscribed() ) {
			if ( ! empty( nab_get_running_experiment_ids() ) || ! empty( nab_get_running_experiment_ids() ) ) {
				return new WP_Error(
					'experiments-already-running-in-free',
					esc_html_x( 'There’s another test currently running. Subscribe to Nelio A/B Testing Premium to run more than one test at a time.', 'user', 'nelio-ab-testing' )
				);
			}//end if
		}//end if

		$running_experiment = $helper->does_overlap_with_running_experiment( $this );
		if ( ! empty( $running_experiment ) ) {
			return new WP_Error(
				'equivalent-experiment-running',
				sprintf(
					/* translators: 1 -> one experiment name, 2 -> another experiment name */
					_x( 'Test %1$s can’t be resumed because there’s another running test that’s testing the same element(s): %2$s.', 'text', 'nelio-ab-testing' ),
					$helper->get_non_empty_name( $this ),
					$helper->get_non_empty_name( $running_experiment )
				)
			);
		}//end if

		return true;

	}//end can_be_resumed()

	/**
	 * Returns the date in which the experiment should be started/was started.
	 *
	 * @return boolean|string the date in which the experiment should be started/was started.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function get_start_date() {

		return $this->start_date;

	}//end get_start_date()

	/**
	 * Sets the date in which the experiment should be started/was started.
	 *
	 * @param string $start_date the date in which the experiment should be started/was started.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function set_start_date( $start_date ) {

		$this->start_date = $start_date;

	}//end set_start_date()

	/**
	 * Returns the date in which the experiment ended.
	 *
	 * @return string the date in which the experiment ended.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function get_end_date() {

		return $this->end_date;

	}//end get_end_date()

	/**
	 * Sets the date in which the experiment ended.
	 *
	 * @param string $end_date the date in which the experiment ended.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function set_end_date( $end_date ) {

		$this->end_date = $end_date;

	}//end set_end_date()

	/**
	 * Returns the end mode.
	 *
	 * @return string the end mode.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function get_end_mode() {

		return $this->end_mode;

	}//end get_end_mode()

	/**
	 * Returns the end value, which depends on the end mode.
	 *
	 * For instance, if the end mode is set to "duration", the end value would be
	 * the number of days the experiment should run until it automatically stops.
	 *
	 * @return mixed the end value.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function get_end_value() {

		return $this->end_value;

	}//end get_end_value()

	/**
	 * Sets the ending properties of this experiment.
	 *
	 * @param string $end_mode  the end mode of this experiment (manual, duration, etc).
	 * @param mixed  $end_value the specific value at which the experiment should end
	 *                          when its mode is other than manual.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function set_end_mode_and_value( $end_mode, $end_value ) {

		$accepted_modes = array( 'manual', 'page-views', 'duration', 'confidence' );
		if ( ! in_array( $end_mode, $accepted_modes, true ) ) {
			$end_mode  = 'manual';
			$end_value = 0;
		}//end if

		$this->end_mode  = $end_mode;
		$this->end_value = $end_value;

	}//end set_end_mode_and_value()

	/**
	 * Gets the starter of the experiment.
	 *
	 * @return mixed The user id of the starter or 'system' if the starter is WordPress.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function get_starter() {
		return $this->starter;
	}//end get_starter()

	/**
	 * Gets the stopper of the experiment.
	 *
	 * @return mixed The user id of the stopper or 'system' if the stopper is WordPress.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function get_stopper() {
		return $this->stopper;
	}//end get_stopper()

	/**
	 * Sets the starter of the experiment.
	 *
	 * @param mixed $starter The user id of the starter or 'system' if the starter is WordPress.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function set_starter( $starter ) {
		$this->starter = $starter;
	}//end set_starter()

	/**
	 * Sets the stopper of the experiment.
	 *
	 * @param mixed $stopper The user id of the stopper or 'system' if the stopper is WordPress.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function set_stopper( $stopper ) {
		$this->stopper = $stopper;
	}//end set_stopper()

	/**
	 * Returns the description of this experiment.
	 *
	 * @return string the description of this experiment.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function get_description() {

		return $this->post->post_excerpt;

	}//end get_description()

	/**
	 * Sets the description of this experiment.
	 *
	 * @param string $description the description of this experiment.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function set_description( $description ) {

		$this->post->post_excerpt = $description;

	}//end set_description()

	/**
	 * Returns the alternatives.
	 *
	 * @return array the alternatives.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function get_alternatives() {

		$alternatives = $this->alternatives;
		if ( ! is_array( $alternatives ) ) {
			$alternatives = array(
				array(
					'id'         => 'control',
					'attributes' => array(),
				),
			);
		}//end if

		// Set default attributes.
		$experiment_type = $this->get_type();
		$alternatives    = array_map(
			function( $alternative ) use ( $experiment_type ) {
				$control_or_alternative = 'control' === $alternative['id'] ? 'control' : 'alternative';
				/**
				 * Filters default attributes for control or alternative.
				 *
				 * @param array $attributes default attributes. Default: `[]`.
				 *
				 * @since 5.0.16
				 */
				$default_attributes        = apply_filters( "nab_{$experiment_type}_get_default_attributes_in_{$control_or_alternative}", array() );
				$alternative['attributes'] = wp_parse_args( $alternative['attributes'], $default_attributes );
				return $alternative;
			},
			$alternatives
		);

		$control                  = $alternatives[0];
		$last_alternative_applied = ! empty( $this->last_alternative_applied ) ? $this->last_alternative_applied : 'control';

		return array_map(
			function( $alternative ) use ( $control, $last_alternative_applied ) {
				$alternative['isLastApplied'] = $alternative['id'] === $last_alternative_applied;
				$alternative['links']         = array(
					'edit'    => $this->get_alternative_edit_link( $alternative, $control ),
					'heatmap' => $this->get_alternative_heatmap_link( $alternative, $control ),
					'preview' => $this->get_alternative_preview_link( $alternative, $control ),
				);
				return $alternative;
			},
			$alternatives
		);

	}//end get_alternatives()

	/**
	 * Returns the alternative.
	 *
	 * @param string $alternative_id the ID of the alternative.
	 *
	 * @return array|boolean the alternative with the given ID or false.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function get_alternative( $alternative_id ) {

		if ( 'control_backup' === $alternative_id ) {
			return $this->control_backup;
		}//end if

		$alternatives = $this->get_alternatives();

		foreach ( $alternatives as $alternative ) {
			if ( $alternative_id === $alternative['id'] ) {
				return $alternative;
			}//end if
		}//end foreach

		return false;

	}//end get_alternative()

	/**
	 * Applies the alternative.
	 *
	 * @param string $alternative_id the ID of the alternative.
	 *
	 * @return boolean|WP_Error whether the alternative has been applied or not.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function apply_alternative( $alternative_id ) {

		if ( 'control' === $alternative_id ) {
			$alternative_id = 'control_backup';
		}//end if

		$alternative = $this->get_alternative( $alternative_id );
		if ( ! $alternative ) {
			$helper = Nelio_AB_Testing_Experiment_Helper::instance();
			return new WP_Error(
				'alternative-not-found',
				sprintf(
					/* translators: 1 -> variant ID, 2 -> experiment name */
					_x( 'Variant %1$s not found in test %2$s.', 'text', 'nelio-ab-testing' ),
					$alternative_id,
					$helper->get_non_empty_name( $this )
				)
			);
		}//end if

		$control         = $this->get_alternative( 'control' );
		$experiment_type = $this->get_type();

		/**
		 * Filter to apply the given alternative.
		 *
		 * This filter is used to apply the given alternative. It returns `true` if the
		 * alternative was properly applied and `false` otherwise.
		 *
		 * @param boolean $applied        whether the alternative was properly applied or not. Default: `false`.
		 * @param array   $alternative    alternative to apply.
		 * @param array   $control        original version.
		 * @param int     $experiment_id  id of the experiment.
		 * @param string  $alternative_id id of the alternative to apply.
		 *
		 * @since 5.0.0
		 */
		$was_alternative_applied = apply_filters( "nab_{$experiment_type}_apply_alternative", false, $alternative['attributes'], $control['attributes'], $this->ID, $alternative_id );

		if ( ! $was_alternative_applied ) {
			$helper = Nelio_AB_Testing_Experiment_Helper::instance();
			return new WP_Error(
				'alternative-could-not-be-applied',
				sprintf(
					/* translators: 1 -> variant ID, 2 -> experiment name */
					_x( 'Variant %1$s in test %2$s couldn’t be applied.', 'text', 'nelio-ab-testing' ),
					$alternative_id,
					$helper->get_non_empty_name( $this )
				)
			);
		}//end if

		if ( 'control_backup' === $alternative_id ) {
			$alternative_id = 'control';
		}//end if
		$this->last_alternative_applied = $alternative_id;
		$this->save();

		return $was_alternative_applied;

	}//end apply_alternative()

	/**
	 * Overwrites the given alternative in the list of alternatives of this experiment.
	 *
	 * @param array $alternative the alternative to overwrite.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function set_alternative( $alternative ) {

		$alternatives = $this->get_alternatives();

		foreach ( $alternatives as $pos => $existing_alternative ) {
			if ( $existing_alternative['id'] !== $alternative['id'] ) {
				continue;
			}//end if
			$alternatives[ $pos ] = $alternative;
		}//end foreach

		$this->set_alternatives( $alternatives );

	}//end set_alternative()

	/**
	 * Sets the alternative list to the given list.
	 *
	 * @param array $alternatives list of alternatives.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function set_alternatives( $alternatives ) {

		$experiment_type = $this->get_type();

		$alternatives = $this->clean_alternatives( $alternatives );

		$new_alternatives = $this->set_ids_as_keys( $alternatives );
		$old_alternatives = $this->set_ids_as_keys( $this->get_alternatives() );

		$removed_alternatives = array_diff_key( $old_alternatives, $new_alternatives );
		foreach ( $removed_alternatives as $key => $removed_alternative ) {

			if ( 'control' === $key ) {
				continue;
			}//end if

			$this->remove_alternative_content( $removed_alternative );

		}//end foreach

		$alternatives_to_create = array_diff_key( $new_alternatives, $old_alternatives );
		foreach ( $new_alternatives as $key => $new_alternative ) {

			if ( 'control' === $key ) {
				continue;
			}//end if

			if ( ! array_key_exists( $key, $alternatives_to_create ) ) {
				continue;
			}//end if

			/**
			 * This filter is triggered when a new alternative has been added to an experiment.
			 *
			 * Hook into this action if you want to perform additional actions to create alternative
			 * content related to this new alternative. Add any extra options/fields in the new
			 * alternative.
			 *
			 * @param array   $new_alternative  current alternative.
			 * @param array   $control          original version.
			 * @param int     $experiment_id    id of the experiment.
			 * @param string  $alternative_id   id of the current alternative.
			 *
			 * @since 5.0.0
			 */
			$new_alternatives[ $key ]['attributes'] = apply_filters( "nab_{$experiment_type}_create_alternative_content", $new_alternative['attributes'], $new_alternatives['control']['attributes'], $this->ID, $key );

		}//end foreach

		$control = $new_alternatives['control'];
		unset( $new_alternatives['control'] );

		$control['id']      = 'control';
		$new_alternatives   = $this->set_keys_as_ids( $new_alternatives );
		$new_alternatives   = $this->remove_dynamic_properties( $new_alternatives );
		$this->alternatives = array_merge(
			array( $control ),
			$new_alternatives
		);

	}//end set_alternatives()

	/**
	 * Returns the goals.
	 *
	 * @return array the goals.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function get_goals() {
		return is_array( $this->goals ) ? $this->goals : array();
	}//end get_goals()

	/**
	 * Sets the goal list to the given list.
	 *
	 * @param array $goals list of goals.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function set_goals( $goals ) {
		if ( ! is_array( $goals ) ) {
			return;
		}//end if
		$this->goals = $goals;
	}//end set_goals()

	/**
	 * Returns the segments.
	 *
	 * @return array the segments.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function get_segments() {
		return is_array( $this->segments ) ? $this->segments : array();
	}//end get_segments()

	/**
	 * Sets the segment list to the given list.
	 *
	 * @param array $segments list of segments.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function set_segments( $segments ) {
		if ( ! is_array( $segments ) ) {
			return;
		}//end if
		$this->segments = $segments;
	}//end set_segments()

	/**
	 * Returns the scope.
	 *
	 * @return array scope.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function get_scope() {
		return is_array( $this->scope ) ? $this->scope : array();
	}//end get_scope()

	/**
	 * Sets the scope of this experiment.
	 *
	 * @param array $scope list of alternatives.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function set_scope( $scope ) {
		if ( ! is_array( $scope ) ) {
			return;
		}//end if
		$this->scope = $scope;
	}//end set_scope()

	/**
	 * Saves this experiment to the database.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function save() {

		$post_id = wp_update_post( $this->post );
		if ( is_wp_error( $post_id ) ) {
			return;
		}//end if

		$this->ID = $post_id;

		update_post_meta( $this->ID, '_nab_start_date', $this->start_date );
		update_post_meta( $this->ID, '_nab_end_date', $this->end_date );
		update_post_meta( $this->ID, '_nab_end_mode', $this->end_mode );
		update_post_meta( $this->ID, '_nab_end_value', $this->end_value );

		$alternatives = $this->get_alternatives();
		$alternatives = $this->remove_dynamic_properties( $alternatives );

		if ( count( $alternatives ) ) {
			update_post_meta( $this->ID, '_nab_alternatives', $alternatives );
		} else {
			delete_post_meta( $this->ID, '_nab_alternatives' );
		}//end if

		$goals = $this->get_goals();
		if ( count( $goals ) ) {
			update_post_meta( $this->ID, '_nab_goals', $goals );
		} else {
			delete_post_meta( $this->ID, '_nab_goals' );
		}//end if

		$segments = $this->get_segments();
		if ( count( $segments ) ) {
			update_post_meta( $this->ID, '_nab_segments', $segments );
		} else {
			delete_post_meta( $this->ID, '_nab_segments' );
		}//end if

		$scope = $this->get_scope();
		if ( count( $scope ) ) {
			update_post_meta( $this->ID, '_nab_scope', $scope );
		} else {
			delete_post_meta( $this->ID, '_nab_scope' );
		}//end if

		$starter = $this->get_starter();
		if ( ! empty( $starter ) ) {
			update_post_meta( $this->ID, '_nab_starter', $starter );
		} else {
			delete_post_meta( $this->ID, '_nab_starter' );
		}//end if

		$stopper = $this->get_stopper();
		if ( ! empty( $stopper ) ) {
			update_post_meta( $this->ID, '_nab_stopper', $stopper );
		} else {
			delete_post_meta( $this->ID, '_nab_stopper' );
		}//end if

		if ( ! empty( $this->control_backup ) ) {
			update_post_meta( $this->ID, '_nab_control_backup', $this->control_backup );
		} else {
			delete_post_meta( $this->ID, '_nab_control_backup' );
		}//end if

		if ( ! empty( $this->last_alternative_applied ) ) {
			update_post_meta( $this->ID, '_nab_last_alternative_applied', $this->last_alternative_applied );
		} else {
			delete_post_meta( $this->ID, '_nab_last_alternative_applied' );
		}//end if

		if ( in_array( $this->get_type(), array( 'nab/page', 'nab/post', 'nab/custom-post-type' ), true ) ) {
			$tested_post_id = $this->get_tested_element();
			if ( $tested_post_id ) {
				update_post_meta( $this->ID, '_nab_tested_post_id', $tested_post_id );
			} else {
				delete_post_meta( $this->ID, '_nab_tested_post_id' );
			}//end if
		}//end if

		/**
		 * Fires after an experiment has been saved.
		 *
		 * @param Nelio_AB_Testing_Experiment $experiment the experiment that has been saved.
		 *
		 * @since 5.0.0
		 */
		do_action( 'nab_save_experiment', $this );

	}//end save()

	/**
	 * Starts this experiment, assuming it's ready.
	 *
	 * @return boolean|WP_Error whether this experiment has been started or not.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function start() {

		$can_be_started = $this->can_be_started();
		if ( is_wp_error( $can_be_started ) ) {
			return $can_be_started;
		}//end if

		$this->set_start_date( str_replace( '+00:00', '.000Z', gmdate( 'c' ) ) );
		$this->post->post_status = 'nab_running';
		if ( empty( $this->get_starter() ) ) {
			$this->set_starter( get_current_user_id() );
		}//end if
		$this->save();

		/**
		 * Fires after an experiment has been started.
		 *
		 * @param Nelio_AB_Testing_Experiment $experiment the experiment that has been started.
		 *
		 * @since 5.0.0
		 */
		do_action( 'nab_start_experiment', $this );

		return true;

	}//end start()

	/**
	 * Resumes this experiment, assuming it's paused.
	 *
	 * @return boolean|WP_Error whether this experiment has been resumed or not.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function resume() {

		$can_be_resumed = $this->can_be_resumed();
		if ( is_wp_error( $can_be_resumed ) ) {
			return $can_be_resumed;
		}//end if

		$this->post->post_status = 'nab_running';
		$this->save();

		/**
		 * Fires after an experiment has been resumed.
		 *
		 * @param Nelio_AB_Testing_Experiment $experiment the experiment that has been resumed.
		 *
		 * @since 5.0.0
		 */
		do_action( 'nab_resume_experiment', $this );

		return true;

	}//end resume()

	/**
	 * Stops this experiment.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function stop() {

		if ( ! in_array( $this->get_status(), array( 'running', 'paused' ), true ) ) {
			$helper = Nelio_AB_Testing_Experiment_Helper::instance();
			return new WP_Error(
				'experiment-cannot-be-stopped',
				sprintf(
					/* translators: experiment name */
					_x( 'Test %1$s can’t be stopped because it’s not running.', 'text', 'nelio-ab-testing' ),
					$helper->get_non_empty_name( $this )
				)
			);
		}//end if

		$this->set_end_date( gmdate( 'c' ) );
		$this->post->post_status = 'nab_finished';
		if ( empty( $this->get_stopper() ) ) {
			$this->set_stopper( get_current_user_id() );
		}//end if

		$this->backup_control_version();
		$this->save();

		/**
		 * Fires after an experiment has been stopped.
		 *
		 * @param Nelio_AB_Testing_Experiment $experiment the experiment that has been stopped.
		 *
		 * @since 5.0.0
		 */
		do_action( 'nab_stop_experiment', $this );

		return true;

	}//end stop()

	/**
	 * Pauses this experiment, assuming it's running.
	 *
	 * @return boolean|WP_Error whether this experiment has been paused or not.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function pause() {

		if ( 'running' !== $this->get_status() ) {
			$helper = Nelio_AB_Testing_Experiment_Helper::instance();
			return new WP_Error(
				'experiment-cannot-be-paused',
				sprintf(
					/* translators: experiment name */
					_x( 'Test %1$s can’t be paused because it’s not running.', 'text', 'nelio-ab-testing' ),
					$helper->get_non_empty_name( $this )
				)
			);
		}//end if

		$this->post->post_status = 'nab_paused';
		$this->save();

		/**
		 * Fires after an experiment has been paused.
		 *
		 * @param Nelio_AB_Testing_Experiment $experiment the experiment that has been paused.
		 *
		 * @since 5.0.0
		 */
		do_action( 'nab_pause_experiment', $this );

		return true;

	}//end pause()

	/**
	 * Returns the status of this experiment.
	 *
	 * @return string the status of this experiment.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function get_status() {
		return str_replace( 'nab_', '', $this->post->post_status );
	}//end get_status()


	/**
	 * Sets the status of this experiment.
	 *
	 * @param string $status the status of this experiment.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function set_status( $status ) {

		if ( in_array( $status, array( 'ready', 'scheduled', 'running', 'paused', 'finished' ), true ) ) {
			$status = 'nab_' . $status;
		}//end if

		$this->post->post_status = $status;

	}//end set_status()

	/**
	 * Returns the experiment URL.
	 *
	 * If the experiment is running or finished, this URL is the results URL. Otherwise, it's the edit URL.
	 *
	 * @return string the experiment URL
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function get_url() {

		if ( in_array( $this->get_status(), array( 'running', 'finished' ), true ) ) {
			$page = 'nelio-ab-testing-experiment-view';
		} else {
			$page = 'nelio-ab-testing-experiment-edit';
		}//end if

		return add_query_arg(
			array(
				'page'       => $page,
				'experiment' => $this->get_id(),
			),
			admin_url( 'admin.php' )
		);

	}//end get_url()

	/**
	 * Returns the preview url of this experiment.
	 *
	 * @return string the preview url of this experiment.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function get_preview_url() {

		$control = $this->get_alternative( 'control' );
		return $control['links']['preview'];

	}//end get_preview_url()

	/**
	 * Callback function to call when an experiment is about to be deleted.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function delete_related_information() {

		$experiment_type = $this->get_type();
		$alternatives    = $this->get_alternatives();

		foreach ( $alternatives as $alternative ) {

			if ( 'control' === $alternative['id'] ) {
				continue;
			}//end if

			$this->remove_alternative_content( $alternative );

		}//end foreach

		if ( $this->control_backup ) {
			$this->remove_alternative_content( $this->control_backup );
		}//end if

	}//end delete_related_information()

	/**
	 * Duplicates the current experiment.
	 *
	 * @return Nelio_AB_Testing_Experiment the new, duplicated experiment.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function duplicate() {

		$experiment_type = $this->get_type();

		$new_experiment = self::create_experiment( $experiment_type );
		$new_experiment->set_name(
			sprintf(
				/* translators: name of a split test */
				_x( 'Copy of %s', 'text', 'nelio-ab-testing' ),
				$this->get_name()
			)
		);
		$new_experiment->set_description( $this->get_description() );
		$new_experiment->set_end_mode_and_value( $this->get_end_mode(), $this->get_end_value() );
		$new_experiment->set_status( 'draft' === $this->get_status() ? 'draft' : 'ready' );

		$goals = $this->get_goals();
		foreach ( $goals as &$goal ) {
			$goal['id'] = nab_uuid();
			foreach ( $goal['conversionActions'] as &$action ) {
				$action['id'] = nab_uuid();
			}//end foreach
		}//end foreach
		$new_experiment->set_goals( $goals );

		$segments = $this->get_segments();
		foreach ( $segments as &$segment ) {
			$segment['id'] = nab_uuid();
			foreach ( $segment['segmentationRules'] as &$rule ) {
				$rule['id'] = nab_uuid();
			}//end foreach
		}//end foreach
		$new_experiment->set_segments( $segments );

		$new_experiment->set_scope( $this->get_scope() );

		$alternatives = $this->get_alternatives();

		foreach ( $alternatives as &$alternative ) {

			unset( $alternative['links'] );

			if ( 'control' === $alternative['id'] ) {
				continue;
			}//end if

			$old_alternative_id = $alternative['id'];
			$alternative['id']  = nab_uuid();

			/**
			 * Fires when an experiment is being duplicated and one of its alternatives is to be duplicated into the new experiment.
			 *
			 * @param array   $new_alternative     new alternative (by default, an exact copy of old alternative).
			 * @param array   $old_alternative     old alternative.
			 * @param int     $new_experiment_id   id of the new experiment.
			 * @param string  $new_alternative_id  id of the new alternative.
			 * @param int     $old_experiment_id   id of the old experiment.
			 * @param string  $old_alternative_id  id of the old alternative.
			 *
			 * @since 5.0.0
			 */
			$alternative['attributes'] = apply_filters( "nab_{$experiment_type}_duplicate_alternative_content", $alternative['attributes'], $alternative['attributes'], $new_experiment->ID, $alternative['id'], $this->ID, $old_alternative_id );

		}//end foreach
		$new_experiment->alternatives = $alternatives;

		$new_experiment->save();

		/**
		 * Fires after an experiment has been duplicated.
		 *
		 * @param Nelio_AB_Testing_Experiment $new_experiment the new experiment.
		 *
		 * @since 5.1.0
		 */
		$new_experiment = apply_filters( 'nab_duplicate_experiment', $new_experiment );

		return $new_experiment;

	}//end duplicate()

	private function remove_alternative_content( $alternative ) {

		$experiment_type = $this->get_type();

		/**
		 * Fires when an alternative is being removed.
		 *
		 * Hook into this filter if the given alternative has some related content that has to
		 * be removed from the database too. For example, when removing a page alternative in
		 * a page experiment, the related page and all its metas have to be removed from
		 * `wp_posts` and `wp_postmeta` respectively.
		 *
		 * @param object $attributes     attributes of this alternative
		 * @param int    $experiment_id  ID of this experiment
		 * @param string $alternative_id ID of the alternative we want to clean
		 *
		 * @since 5.0.0
		 */
		do_action( "nab_{$experiment_type}_remove_alternative_content", $alternative['attributes'], $this->ID, $alternative['id'] );

	}//end remove_alternative_content()

	private function remove_dynamic_properties( $alternatives ) {

		return array_map(
			function( $alternative ) {
				if ( isset( $alternative['links'] ) ) {
					unset( $alternative['links'] );
				}//end if
				if ( isset( $alternative['isLastApplied'] ) ) {
					unset( $alternative['isLastApplied'] );
				}//end if
				return $alternative;
			},
			$alternatives
		);

	}//end remove_dynamic_properties()

	private function clean_alternatives( $alternatives ) {

		return array_map(
			function( $alternative ) {
				return array(
					'id'         => $alternative['id'],
					'attributes' => $alternative['attributes'],
				);
			},
			$alternatives
		);

	}//end clean_alternatives()

	private function get_alternative_edit_link( $alternative, $control ) {

		$experiment_id   = $this->get_id();
		$alternative_id  = $alternative['id'];
		$experiment_type = $this->get_type();

		/**
		 * Filters the edit link of the given alternative.
		 *
		 * @param mixed   $edit_link      link for editing the given alternative. Default: `false`.
		 * @param array   $alternative    current alternative.
		 * @param array   $control        original version.
		 * @param int     $experiment_id  id of the experiment.
		 * @param string  $alternative_id id of the current alternative.
		 *
		 * @since 5.0.0
		 */
		return apply_filters( "nab_{$experiment_type}_edit_link_alternative", false, $alternative['attributes'], $control['attributes'], $experiment_id, $alternative_id );

	}//end get_alternative_edit_link()

	private function get_alternative_heatmap_link( $alternative, $control ) {

		if ( ! did_action( 'init' ) ) {
			return false;
		}//end if

		$experiment_id     = $this->get_id();
		$alternative_id    = $alternative['id'];
		$experiment_type   = $this->get_type();
		$alternative_attrs = $alternative['attributes'];

		if ( 'finished' === $this->get_status() && 'control' === $alternative_id ) {
			if ( empty( $this->control_backup ) ) {
				return false;
			}//end if
			$alternative_attrs = $this->control_backup['attributes'];
		}//end if

		/**
		 * Filters the heatmap link of the given alternative.
		 *
		 * @param mixed   $heatmap_link   link for viewing the heatmaps of the given alternative. Default: `false`.
		 * @param array   $alternative    current alternative.
		 * @param array   $control        original version.
		 * @param int     $experiment_id  id of the experiment.
		 * @param string  $alternative_id id of the current alternative.
		 *
		 * @since 5.0.0
		 */
		$heatmap_url = apply_filters( "nab_{$experiment_type}_heatmap_link_alternative", false, $alternative_attrs, $control['attributes'], $experiment_id, $alternative_id );
		if ( ! $heatmap_url ) {
			return false;
		}//end if

		$secret       = nab_get_api_secret();
		$preview_time = time();
		return add_query_arg(
			array(
				'nab-preview'          => true,
				'nab-heatmap-renderer' => true,
				'experiment'           => $experiment_id,
				'alternative'          => $alternative_id,
				'timestamp'            => $preview_time,
				'nonce'                => md5( "nab-preview-{$experiment_id}-{$alternative_id}-{$preview_time}-{$secret}" ),
			),
			$heatmap_url
		);

	}//end get_alternative_heatmap_link()

	private function get_alternative_preview_link( $alternative, $control ) {

		if ( ! did_action( 'init' ) ) {
			return false;
		}//end if

		$experiment_id     = $this->get_id();
		$alternative_id    = $alternative['id'];
		$experiment_type   = $this->get_type();
		$alternative_attrs = $alternative['attributes'];

		if ( 'finished' === $this->get_status() && 'control' === $alternative_id ) {
			if ( empty( $this->control_backup ) ) {
				return false;
			}//end if
			$alternative_attrs = $this->control_backup['attributes'];
		}//end if

		/**
		 * Filters the preview link of the given alternative.
		 *
		 * @param mixed   $edit_link      link for previewing the given alternative. Default: `false`.
		 * @param array   $alternative    current alternative.
		 * @param array   $control        original version.
		 * @param int     $experiment_id  id of the experiment.
		 * @param string  $alternative_id id of the current alternative.
		 *
		 * @since 5.0.0
		 */
		$preview_url = apply_filters( "nab_{$experiment_type}_preview_link_alternative", false, $alternative_attrs, $control['attributes'], $experiment_id, $alternative_id );
		if ( ! $preview_url ) {
			return false;
		}//end if

		$secret       = nab_get_api_secret();
		$preview_time = time();
		return add_query_arg(
			array(
				'nab-preview' => true,
				'experiment'  => $experiment_id,
				'alternative' => $alternative_id,
				'timestamp'   => $preview_time,
				'nonce'       => md5( "nab-preview-{$experiment_id}-{$alternative_id}-{$preview_time}-{$secret}" ),
			),
			$preview_url
		);

	}//end get_alternative_preview_link()

	/**
	 * Creates a new associative array where the keys are the IDs of the objects included in the original array.
	 *
	 * @param array $elements the alternative list.
	 *
	 * @return array an associative array where the keys are the IDs of the objects included in the original array.
	 *
	 * @since  5.0.0
	 * @access private
	 */
	private function set_ids_as_keys( $elements ) {

		$result = array();
		if ( empty( $elements ) ) {
			return $result;
		}//end if

		foreach ( $elements as $elem ) {
			$element_id = $elem['id'];
			unset( $elem['id'] );
			$result[ $element_id ] = $elem;
		}//end foreach

		return $result;

	}//end set_ids_as_keys()

	/**
	 * Creates a non associative array where the keys in the original array are an attribute in each object.
	 *
	 * @param array $elements the alternative list.
	 *
	 * @return array a non associative array where the keys in the original array are an attribute in each object.
	 *
	 * @since  5.0.0
	 * @access private
	 */
	private function set_keys_as_ids( $elements ) {

		$result = array();
		foreach ( $elements as $key => $elem ) {
			$elem['id'] = $key;
			array_push( $result, $elem );
		}//end foreach
		return $result;

	}//end set_keys_as_ids()

	private function get_tested_post_from_scope() {

		$control = $this->get_alternative( 'control' );
		foreach ( $this->get_scope() as $rule ) {
			if ( 'tested-post' === $rule['attributes']['type'] ) {
				return absint( $this->get_tested_element() );
			}//end if
		}//end foreach

		return 0;

	}//end get_tested_post_from_scope()

	/**
	 * Backups the current control version.
	 *
	 * @since  5.0.0
	 */
	public function backup_control_version() {

		$experiment_type = $this->get_type();
		$control         = $this->get_alternative( 'control' );

		$backup = array(
			'id'         => 'control_backup',
			'attributes' => array(),
		);

		/**
		 * Fires when an experiment is stopped and a backup of the control version has to be generated.
		 *
		 * @param array   $backup           the backup object.
		 * @param array   $control          original version.
		 * @param int     $experiment_id    id of the experiment.
		 * @param string  $alternative_id   id of the current alternative.
		 *
		 * @since 5.0.0
		 */
		$backup['attributes'] = apply_filters( "nab_{$experiment_type}_backup_control", $backup['attributes'], $control['attributes'], $this->ID, $backup['id'] );

		if ( empty( $backup['attributes'] ) ) {
			$this->control_backup = false;
		}//end if

		$this->control_backup = $backup;

	}//end backup_control_version()

}//end class
